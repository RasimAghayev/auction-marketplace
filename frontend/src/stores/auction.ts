import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import type { Auction, Bid, PaginatedResponse } from '@/types'

declare global {
  interface Window {
    Pusher: typeof Pusher
    Echo: Echo<'reverb'>
  }
}

export const useAuctionStore = defineStore('auction', () => {
  const auctions = ref<Auction[]>([])
  const currentAuction = ref<Auction | null>(null)
  const bids = ref<Bid[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
  })

  let echo: Echo<'reverb'> | null = null
  let auctionChannel: any = null

  function initializeEcho(token: string) {
    if (echo) return echo

    window.Pusher = Pusher

    echo = new Echo({
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY || 'auction-key',
      wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
      wsPort: parseInt(import.meta.env.VITE_REVERB_PORT || '8080'),
      wssPort: parseInt(import.meta.env.VITE_REVERB_PORT || '8080'),
      forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
      enabledTransports: ['ws', 'wss'],
      auth: {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      },
    })

    window.Echo = echo
    return echo
  }

  function joinAuctionChannel(auctionId: string) {
    if (!echo) return

    if (auctionChannel) {
      leaveAuctionChannel()
    }

    auctionChannel = echo.channel(`auction.${auctionId}`)

    auctionChannel.listen('.bid.placed', (data: any) => {
      handleBidUpdate(data)
    })

    auctionChannel.listen('.auction.updated', (data: any) => {
      handleAuctionUpdate(data)
    })

    auctionChannel.listen('.auction.ended', (data: any) => {
      handleAuctionEnded(data)
    })
  }

  function leaveAuctionChannel() {
    if (auctionChannel && echo) {
      echo.leaveChannel(`auction.${currentAuction.value?.id}`)
      auctionChannel = null
    }
  }

  function handleBidUpdate(data: any) {
    if (currentAuction.value && currentAuction.value.id === data.auction_id) {
      currentAuction.value.current_price = data.bid.amount
      currentAuction.value.total_bids = data.auction.total_bids
      currentAuction.value.version = data.auction.version
    }

    const newBid: Bid = {
      id: data.bid.id,
      auction_id: data.bid.auction_id,
      user_id: data.bid.user_id,
      amount: data.bid.amount,
      status: data.bid.status,
      request_id: data.bid.request_id,
      user: data.bid.user,
      created_at: data.bid.created_at,
      updated_at: data.bid.updated_at,
    }

    bids.value.unshift(newBid)
    bids.value = bids.value.slice(0, 50)
  }

  function handleAuctionUpdate(data: any) {
    if (currentAuction.value && currentAuction.value.id === data.auction.id) {
      Object.assign(currentAuction.value, data.auction)
    }

    const index = auctions.value.findIndex(a => a.id === data.auction.id)
    if (index !== -1) {
      Object.assign(auctions.value[index], data.auction)
    }
  }

  function handleAuctionEnded(data: any) {
    if (currentAuction.value && currentAuction.value.id === data.auction.id) {
      currentAuction.value.status = 'completed'
      currentAuction.value.winner_id = data.auction.winner_id
      currentAuction.value.completed_at = data.auction.completed_at
    }

    const index = auctions.value.findIndex(a => a.id === data.auction.id)
    if (index !== -1) {
      auctions.value[index].status = 'completed'
      auctions.value[index].winner_id = data.auction.winner_id
    }
  }

  async function fetchAuctions(params: Record<string, any> = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get<PaginatedResponse<Auction>>('/api/v1/auctions', { params })
      auctions.value = response.data.data
      pagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        per_page: response.data.per_page,
        total: response.data.total,
      }
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch auctions'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchAuction(id: string) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get<{ auction: Auction }>(`/api/v1/auctions/${id}`)
      currentAuction.value = response.data.auction
      return response.data.auction
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch auction'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchAuctionBids(auctionId: string, params: Record<string, any> = {}) {
    try {
      const response = await axios.get<PaginatedResponse<Bid>>(`/api/v1/auctions/${auctionId}/bids`, { params })
      bids.value = response.data.data
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch bids'
      throw err
    }
  }

  async function placeBid(auctionId: string, amount: number, requestId: string) {
    try {
      const response = await axios.post<{ bid: Bid; auction: Auction }>(
        `/api/v1/auctions/${auctionId}/bids`,
        { amount, request_id: requestId }
      )
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to place bid'
      throw err
    }
  }

  async function createAuction(data: FormData) {
    try {
      const response = await axios.post<{ auction: Auction }>('/api/v1/auctions', data, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create auction'
      throw err
    }
  }

  async function startAuction(auctionId: string) {
    try {
      const response = await axios.post<{ auction: Auction }>(`/api/v1/auctions/${auctionId}/start`)
      if (currentAuction.value && currentAuction.value.id === auctionId) {
        Object.assign(currentAuction.value, response.data.auction)
      }
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to start auction'
      throw err
    }
  }

  async function cancelAuction(auctionId: string) {
    try {
      const response = await axios.post<{ auction: Auction }>(`/api/v1/auctions/${auctionId}/cancel`)
      if (currentAuction.value && currentAuction.value.id === auctionId) {
        Object.assign(currentAuction.value, response.data.auction)
      }
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to cancel auction'
      throw err
    }
  }

  function clearCurrentAuction() {
    currentAuction.value = null
    bids.value = []
    leaveAuctionChannel()
  }

  function clearError() {
    error.value = null
  }

  return {
    auctions,
    currentAuction,
    bids,
    loading,
    error,
    pagination,
    initializeEcho,
    joinAuctionChannel,
    leaveAuctionChannel,
    fetchAuctions,
    fetchAuction,
    fetchAuctionBids,
    placeBid,
    createAuction,
    startAuction,
    cancelAuction,
    clearCurrentAuction,
    clearError,
  }
})