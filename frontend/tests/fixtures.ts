import { test as base, expect } from '@playwright/test'
import { Page } from '@playwright/test'

interface TestFixtures {
  authenticatedPage: Page
  sellerPage: Page
  bidderPage: Page
  apiRequest: any
}

export const test = base.extend<TestFixtures>({
  authenticatedPage: async ({ page }, use) => {
    await page.goto('/login')
    await page.fill('input[name="email"]', 'test@example.com')
    await page.fill('input[name="password"]', 'password123')
    await page.click('button[type="submit"]')
    await page.waitForURL('**/dashboard')
    await use(page)
  },

  sellerPage: async ({ page }, use) => {
    await page.goto('/login')
    await page.fill('input[name="email"]', 'seller@example.com')
    await page.fill('input[name="password"]', 'password123')
    await page.click('button[type="submit"]')
    await page.waitForURL('**/dashboard')
    await use(page)
  },

  bidderPage: async ({ page }, use) => {
    await page.goto('/login')
    await page.fill('input[name="email"]', 'bidder@example.com')
    await page.fill('input[name="password"]', 'password123')
    await page.click('button[type="submit"]')
    await page.waitForURL('**/dashboard')
    await use(page)
  },

  apiRequest: async ({ request }, use) => {
    const apiBase = process.env.API_BASE_URL || 'http://localhost/api'
    
    const api = {
      async login(email: string, password: string) {
        const response = await request.post(`${apiBase}/auth/login`, {
          data: { email, password },
        })
        return response.json()
      },

      async register(data: any) {
        const response = await request.post(`${apiBase}/auth/register`, {
          data,
        })
        return response.json()
      },

      async getAuctions(token?: string) {
        const headers: any = {}
        if (token) headers.Authorization = `Bearer ${token}`
        const response = await request.get(`${apiBase}/auctions`, { headers })
        return response.json()
      },

      async getAuction(id: string, token?: string) {
        const headers: any = {}
        if (token) headers.Authorization = `Bearer ${token}`
        const response = await request.get(`${apiBase}/auctions/${id}`, { headers })
        return response.json()
      },

      async createAuction(data: any, token: string) {
        const response = await request.post(`${apiBase}/auctions`, {
          headers: { Authorization: `Bearer ${token}` },
          data,
        })
        return response.json()
      },

      async placeBid(auctionId: string, amount: number, token: string) {
        const response = await request.post(`${apiBase}/auctions/${auctionId}/bids`, {
          headers: { Authorization: `Bearer ${token}` },
          data: { amount },
        })
        return response.json()
      },

      async getMyBids(token: string) {
        const response = await request.get(`${apiBase}/bids/my-bids`, {
          headers: { Authorization: `Bearer ${token}` },
        })
        return response.json()
      },

      async getNotifications(token: string) {
        const response = await request.get(`${apiBase}/notifications`, {
          headers: { Authorization: `Bearer ${token}` },
        })
        return response.json()
      },

      async markNotificationRead(id: string, token: string) {
        const response = await request.put(`${apiBase}/notifications/${id}/read`, {
          headers: { Authorization: `Bearer ${token}` },
        })
        return response.json()
      },
    }

    await use(api)
  },
})

export { expect } from '@playwright/test'