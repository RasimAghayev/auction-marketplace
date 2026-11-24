import { test, expect, request } from '@playwright/test'

const API_BASE = process.env.API_BASE_URL || 'http://localhost/api'

test.describe('Authentication API', () => {
  test('POST /api/auth/register - registers new user', async ({ request }) => {
    const email = `test${Date.now()}@example.com`
    const response = await request.post(`${API_BASE}/auth/register`, {
      data: {
        name: 'Test User',
        email,
        password: 'password123',
        password_confirmation: 'password123',
      },
    })

    expect(response.status()).toBe(201)
    const body = await response.json()
    expect(body).toHaveProperty('user')
    expect(body).toHaveProperty('token')
    expect(body.user.email).toBe(email)
  })

  test('POST /api/auth/register - fails with duplicate email', async ({ request }) => {
    const response = await request.post(`${API_BASE}/auth/register`, {
      data: {
        name: 'Test User',
        email: 'existing@example.com',
        password: 'password123',
        password_confirmation: 'password123',
      },
    })

    expect(response.status()).toBe(422)
  })

  test('POST /api/auth/login - logs in user', async ({ request }) => {
    const response = await request.post(`${API_BASE}/auth/login`, {
      data: {
        email: 'test@example.com',
        password: 'password123',
      },
    })

    expect(response.status()).toBe(200)
    const body = await response.json()
    expect(body).toHaveProperty('user')
    expect(body).toHaveProperty('token')
  })

  test('POST /api/auth/login - fails with wrong credentials', async ({ request }) => {
    const response = await request.post(`${API_BASE}/auth/login`, {
      data: {
        email: 'test@example.com',
        password: 'wrongpassword',
      },
    })

    expect(response.status()).toBe(401)
  })
})

test.describe('Categories API', () => {
  test('GET /api/categories - returns list of categories', async ({ request }) => {
    const response = await request.get(`${API_BASE}/categories`)

    expect(response.status()).toBe(200)
    const body = await response.json()
    expect(body).toHaveProperty('data')
    expect(Array.isArray(body.data)).toBe(true)
  })

  test('GET /api/categories/:id - returns single category', async ({ request }) => {
    const response = await request.get(`${API_BASE}/categories/1`)

    expect(response.status()).toBe(200)
    const body = await response.json()
    expect(body).toHaveProperty('data')
    expect(body.data).toHaveProperty('id')
  })
})

test.describe('Products API', () => {
  let authToken: string

  test.beforeAll(async ({ request }) => {
    const login = await request.post(`${API_BASE}/auth/login`, {
      data: { email: 'seller@example.com', password: 'password123' },
    })
    const body = await login.json()
    authToken = body.token
  })

  test('GET /api/products - returns published products', async ({ request }) => {
    const response = await request.get(`${API_BASE}/products`)

    expect(response.status()).toBe(200)
    const body = await response.json()
    expect(body).toHaveProperty('data')
    expect(Array.isArray(body.data)).toBe(true)
  })

  test('GET /api/products/:slug - returns product details', async ({ request }) => {
    const response = await request.get(`${API_BASE}/products/test-product`)

    expect(response.status()).toBe(200)
    const body = await response.json()
    expect(body).toHaveProperty('data')
    expect(body.data).toHaveProperty('title')
  })

  test('POST /api/products - creates product as seller', async ({ request }) => {
    const response = await request.post(`${API_BASE}/products`, {
      headers: { Authorization: `Bearer ${authToken}` },
      data: {
        title: 'API Test Product',
        description: 'Created via API',
        category_id: '1',
        condition: 'new',
        starting_price: 100,
        reserve_price: 150,
        currency: 'USD',
      },
    })

    expect(response.status()).toBe(201)
    const body = await response.json()
    expect(body.data.title).toBe('API Test Product')
  })

  test('POST /api/products - fails without authentication', async ({ request }) => {
    const response = await request.post(`${API_BASE}/products`, {
      data: { title: 'Test' },
    })

    expect(response.status()).toBe(401)
  })
})

test.describe('Auctions API', () => {
  let authToken: string
  let productId: string

  test.beforeAll(async ({ request }) => {
    const login = await request.post(`${API_BASE}/auth/login`, {
      data: { email: 'seller@example.com', password: 'password123' },
    })
    const body = await login.json()
    authToken = body.token

    // Create a product first
    const product = await request.post(`${API_BASE}/products`, {
      headers: { Authorization: `Bearer ${authToken}` },
      data: {
        title: 'Auction Test Product',
        description: 'For auction',
        category_id: '1',
        condition: 'new',
        starting_price: 100,
        reserve_price: 200,
        currency: 'USD',
      },
    })
    const productBody = await product.json()
    productId = productBody.data.id

    // Publish it
    await request.post(`${API_BASE}/products/${productId}/publish`, {
      headers: { Authorization: `Bearer ${authToken}` },
    })
  })

  test('GET /api/auctions - returns active auctions', async ({ request }) => {
    const response = await request.get(`${API_BASE}/auctions`)

    expect(response.status()).toBe(200)
    const body = await response.json()
    expect(body).toHaveProperty('data')
    expect(Array.isArray(body.data)).toBe(true)
  })

  test('GET /api/auctions/:id - returns auction details', async ({ request }) => {
    // Create an auction first
    const auction = await request.post(`${API_BASE}/auctions`, {
      headers: { Authorization: `Bearer ${authToken}` },
      data: {
        product_id: productId,
        starting_price: 100,
        min_bid_increment: 10,
        reserve_price: 200,
        start_date: new Date(Date.now() + 60000).toISOString(),
        end_date: new Date(Date.now() + 3600000).toISOString(),
      },
    })
    const auctionBody = await auction.json()
    const auctionId = auctionBody.data.id

    const response = await request.get(`${API_BASE}/auctions/${auctionId}`)
    expect(response.status()).toBe(200)
    const body = await response.json()
    expect(body.data.id).toBe(auctionId)
  })

  test('POST /api/auctions - creates auction as seller', async ({ request }) => {
    const response = await request.post(`${API_BASE}/auctions`, {
      headers: { Authorization: `Bearer ${authToken}` },
      data: {
        product_id: productId,
        starting_price: 100,
        min_bid_increment: 10,
        reserve_price: 200,
        start_date: new Date(Date.now() + 60000).toISOString(),
        end_date: new Date(Date.now() + 3600000).toISOString(),
      },
    })

    expect(response.status()).toBe(201)
    const body = await response.json()
    expect(body.data.product_id).toBe(productId)
  })

  test('POST /api/auctions/:id/bids - places bid', async ({ request }) => {
    // Create auction
    const auction = await request.post(`${API_BASE}/auctions`, {
      headers: { Authorization: `Bearer ${authToken}` },
      data: {
        product_id: productId,
        starting_price: 100,
        min_bid_increment: 10,
        reserve_price: 200,
        start_date: new Date(Date.now() - 60000).toISOString(), // Started
        end_date: new Date(Date.now() + 3600000).toISOString(),
      },
    })
    const auctionBody = await auction.json()
    const auctionId = auctionBody.data.id

    // Login as bidder
    const bidderLogin = await request.post(`${API_BASE}/auth/login`, {
      data: { email: 'bidder@example.com', password: 'password123' },
    })
    const bidderBody = await bidderLogin.json()

    // Place bid
    const response = await request.post(`${API_BASE}/auctions/${auctionId}/bids`, {
      headers: { Authorization: `Bearer ${bidderBody.token}` },
      data: { amount: 120 },
    })

    expect(response.status()).toBe(201)
    const body = await response.json()
    expect(body.data.amount).toBe(120)
  })

  test('POST /api/auctions/:id/bids - rejects bid below minimum', async ({ request }) => {
    // Create auction
    const auction = await request.post(`${API_BASE}/auctions`, {
      headers: { Authorization: `Bearer ${authToken}` },
      data: {
        product_id: productId,
        starting_price: 100,
        min_bid_increment: 10,
        reserve_price: 200,
        start_date: new Date(Date.now() - 60000).toISOString(),
        end_date: new Date(Date.now() + 3600000).toISOString(),
      },
    })
    const auctionBody = await auction.json()
    const auctionId = auctionBody.data.id

    const bidderLogin = await request.post(`${API_BASE}/auth/login`, {
      data: { email: 'bidder@example.com', password: 'password123' },
    })
    const bidderBody = await bidderLogin.json()

    const response = await request.post(`${API_BASE}/auctions/${auctionId}/bids`, {
      headers: { Authorization: `Bearer ${bidderBody.token}` },
      data: { amount: 105 }, // Below minimum of 110
    })

    expect(response.status()).toBe(422)
  })
})

test.describe('Bids API', () => {
  let authToken: string

  test.beforeAll(async ({ request }) => {
    const login = await request.post(`${API_BASE}/auth/login`, {
      data: { email: 'bidder@example.com', password: 'password123' },
    })
    const body = await login.json()
    authToken = body.token
  })

  test('GET /api/bids/my-bids - returns user bids', async ({ request }) => {
    const response = await request.get(`${API_BASE}/bids/my-bids`, {
      headers: { Authorization: `Bearer ${authToken}` },
    })

    expect(response.status()).toBe(200)
    const body = await response.json()
    expect(body).toHaveProperty('data')
    expect(Array.isArray(body.data)).toBe(true)
  })
})

test.describe('Notifications API', () => {
  let authToken: string

  test.beforeAll(async ({ request }) => {
    const login = await request.post(`${API_BASE}/auth/login`, {
      data: { email: 'test@example.com', password: 'password123' },
    })
    const body = await login.json()
    authToken = body.token
  })

  test('GET /api/notifications - returns user notifications', async ({ request }) => {
    const response = await request.get(`${API_BASE}/notifications`, {
      headers: { Authorization: `Bearer ${authToken}` },
    })

    expect(response.status()).toBe(200)
    const body = await response.json()
    expect(body).toHaveProperty('data')
    expect(Array.isArray(body.data)).toBe(true)
  })

  test('PUT /api/notifications/:id/read - marks notification as read', async ({ request }) => {
    // First get notifications to find one
    const list = await request.get(`${API_BASE}/notifications`, {
      headers: { Authorization: `Bearer ${authToken}` },
    })
    const listBody = await list.json()

    if (listBody.data.length > 0) {
      const notificationId = listBody.data[0].id
      const response = await request.put(`${API_BASE}/notifications/${notificationId}/read`, {
        headers: { Authorization: `Bearer ${authToken}` },
      })

      expect(response.status()).toBe(200)
    }
  })

  test('PUT /api/notifications/read-all - marks all as read', async ({ request }) => {
    const response = await request.put(`${API_BASE}/notifications/read-all`, {
      headers: { Authorization: `Bearer ${authToken}` },
    })

    expect(response.status()).toBe(200)
  })
})

test.describe('Search API', () => {
  test('GET /api/search/products - searches products', async ({ request }) => {
    const response = await request.get(`${API_BASE}/search/products?q=iPhone`)

    expect(response.status()).toBe(200)
    const body = await response.json()
    expect(body).toHaveProperty('data')
    expect(Array.isArray(body.data)).toBe(true)
  })

  test('GET /api/search/products - filters by category', async ({ request }) => {
    const response = await request.get(`${API_BASE}/search/products?category=electronics`)

    expect(response.status()).toBe(200)
    const body = await response.json()
    expect(body).toHaveProperty('data')
  })

  test('GET /api/search/products - filters by price range', async ({ request }) => {
    const response = await request.get(`${API_BASE}/search/products?min_price=100&max_price=500`)

    expect(response.status()).toBe(200)
    const body = await response.json()
    expect(body).toHaveProperty('data')
  })
})

test.describe('Health Check', () => {
  test('GET /health - returns healthy', async ({ request }) => {
    const response = await request.get('http://localhost/health')
    expect(response.status()).toBe(200)
    expect(await response.text()).toContain('healthy')
  })

  test('GET /api/health - returns API health', async ({ request }) => {
    const response = await request.get(`${API_BASE}/health`)
    expect(response.status()).toBe(200)
    const body = await response.json()
    expect(body.status).toBe('ok')
  })
})