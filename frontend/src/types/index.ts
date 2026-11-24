export interface User {
  id: string
  name: string
  email: string
  email_verified_at: string | null
  role: 'admin' | 'user' | 'seller'
  avatar: string | null
  phone: string | null
  address: Address | null
  is_active: boolean
  last_login_at: string | null
  created_at: string
  updated_at: string
  roles?: Role[]
  permissions?: Permission[]
}

export interface Role {
  id: string
  name: string
  guard_name: string
}

export interface Permission {
  id: string
  name: string
  guard_name: string
}

export interface Address {
  street: string
  city: string
  state: string
  postal_code: string
  country: string
}

export interface Category {
  id: string
  name: string
  slug: string
  description: string | null
  parent_id: string | null
  image: string | null
  is_active: boolean
  sort_order: number
  created_at: string
  updated_at: string
  children?: Category[]
  parent?: Category
  products_count?: number
}

export interface ProductImage {
  url: string
  alt: string | null
  is_primary: boolean
  sort_order: number
}

export interface Product {
  id: string
  seller_id: string
  title: string
  slug: string
  description: string
  category_id: string
  images: ProductImage[]
  condition: 'new' | 'used'
  starting_price: number
  reserve_price: number | null
  currency: string
  status: 'draft' | 'published' | 'in_auction' | 'sold' | 'archived'
  meta: Record<string, any> | null
  created_at: string
  updated_at: string
  deleted_at: string | null
  seller?: User
  category?: Category
  auction?: Auction
}

export interface Auction {
  id: string
  product_id: string
  seller_id: string
  starting_price: number
  current_price: number
  min_bid_increment: number
  reserve_price: number | null
  start_date: string
  end_date: string
  status: 'scheduled' | 'active' | 'completed' | 'cancelled' | 'unsold'
  winner_id: string | null
  total_bids: number
  version: number
  meta: Record<string, any> | null
  created_at: string
  updated_at: string
  deleted_at: string | null
  cancelled_at: string | null
  completed_at: string | null
  product?: Product
  seller?: User
  winner?: User
  bids?: Bid[]
}

export interface Bid {
  id: string
  auction_id: string
  user_id: string
  amount: number
  status: 'pending' | 'accepted' | 'rejected' | 'outbid' | 'winning' | 'won' | 'lost'
  request_id: string
  meta: Record<string, any> | null
  created_at: string
  updated_at: string
  user?: User
  auction?: Auction
}

export interface AuctionParticipant {
  id: string
  auction_id: string
  user_id: string
  first_bid_at: string | null
  last_bid_at: string | null
  total_bids: number
  highest_bid: number | null
  is_watcher: boolean
  created_at: string
  updated_at: string
  user?: User
}

export interface AuctionHistory {
  id: string
  auction_id: string
  user_id: string | null
  event_type: string
  data: Record<string, any>
  created_at: string
  auction?: Auction
  user?: User
}

export interface Notification {
  id: string
  user_id: string
  type: string
  title: string
  message: string | null
  data: Record<string, any>
  read_at: string | null
  created_at: string
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

export interface ApiResponse<T> {
  message: string
  data?: T
  errors?: Record<string, string[]>
}

export interface AuctionFilters {
  status?: string
  category_id?: string
  min_price?: number
  max_price?: number
  search?: string
  sort?: string
  page?: number
  per_page?: number
}

export interface ProductFilters {
  category_id?: string
  condition?: string
  status?: string
  min_price?: number
  max_price?: number
  search?: string
  sort?: string
  page?: number
  per_page?: number
}