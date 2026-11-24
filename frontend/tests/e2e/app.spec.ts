import { test, expect } from '@playwright/test'

test.describe('Authentication Flow', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/')
  })

  test('user can register successfully', async ({ page }) => {
    await page.click('a[href="/register"]')
    await expect(page).toHaveURL(/.*register/)

    await page.fill('input[name="name"]', 'Test User')
    await page.fill('input[name="email"]', `test${Date.now()}@example.com`)
    await page.fill('input[name="password"]', 'password123')
    await page.fill('input[name="password_confirmation"]', 'password123')

    await page.click('button[type="submit"]')

    await expect(page).toHaveURL(/.*dashboard/)
    await expect(page.locator('text=Welcome')).toBeVisible()
  })

  test('user can login successfully', async ({ page }) => {
    await page.click('a[href="/login"]')
    await expect(page).toHaveURL(/.*login/)

    await page.fill('input[name="email"]', 'test@example.com')
    await page.fill('input[name="password"]', 'password123')

    await page.click('button[type="submit"]')

    await expect(page).toHaveURL(/.*dashboard/)
    await expect(page.locator('text=Welcome')).toBeVisible()
  })

  test('login fails with wrong password', async ({ page }) => {
    await page.click('a[href="/login"]')

    await page.fill('input[name="email"]', 'test@example.com')
    await page.fill('input[name="password"]', 'wrongpassword')

    await page.click('button[type="submit"]')

    await expect(page.locator('text=Invalid credentials')).toBeVisible()
  })

  test('authenticated user can logout', async ({ page }) => {
    // Login first
    await page.click('a[href="/login"]')
    await page.fill('input[name="email"]', 'test@example.com')
    await page.fill('input[name="password"]', 'password123')
    await page.click('button[type="submit"]')

    await expect(page).toHaveURL(/.*dashboard/)

    // Logout
    await page.click('[data-testid="user-menu"]')
    await page.click('text=Logout')

    await expect(page).toHaveURL('/')
    await expect(page.locator('a[href="/login"]')).toBeVisible()
  })
})

test.describe('Auction Browsing', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/auctions')
  })

  test('displays list of active auctions', async ({ page }) => {
    await expect(page.locator('text=Active Auctions')).toBeVisible()
    await expect(page.locator('[data-testid="auction-card"]')).toHaveCountGreaterThan(0)
  })

  test('shows auction details on click', async ({ page }) => {
    const firstAuction = page.locator('[data-testid="auction-card"]').first()
    const title = await firstAuction.locator('[data-testid="auction-title"]').textContent()

    await firstAuction.click()

    await expect(page).toHaveURL(/.*auctions\/.*/)
    await expect(page.locator('h1')).toContainText(title || '')
  })

  test('displays auction information correctly', async ({ page }) => {
    const firstAuction = page.locator('[data-testid="auction-card"]').first()
    await firstAuction.click()

    await expect(page.locator('[data-testid="current-price"]')).toBeVisible()
    await expect(page.locator('[data-testid="time-remaining"]')).toBeVisible()
    await expect(page.locator('[data-testid="bid-count"]')).toBeVisible()
    await expect(page.locator('[data-testid="min-bid"]')).toBeVisible()
  })

  test('filters auctions by category', async ({ page }) => {
    await page.selectOption('select[name="category"]', 'electronics')
    await expect(page.locator('[data-testid="auction-card"]')).toHaveCountGreaterThan(0)
  })

  test('searches auctions by keyword', async ({ page }) => {
    await page.fill('input[name="search"]', 'iPhone')
    await page.press('input[name="search"]', 'Enter')

    await expect(page.locator('[data-testid="auction-card"]')).toHaveCountGreaterThan(0)
  })
})

test.describe('Bidding Flow', () => {
  test.beforeEach(async ({ page }) => {
    // Login as bidder
    await page.goto('/login')
    await page.fill('input[name="email"]', 'bidder@example.com')
    await page.fill('input[name="password"]', 'password123')
    await page.click('button[type="submit"]')
    await expect(page).toHaveURL(/.*dashboard/)
  })

  test('user can place a valid bid', async ({ page }) => {
    await page.goto('/auctions')
    const firstAuction = page.locator('[data-testid="auction-card"]').first()
    await firstAuction.click()

    const currentPrice = await page.locator('[data-testid="current-price"]').textContent()
    const minBid = await page.locator('[data-testid="min-bid"]').textContent()

    await page.fill('input[name="amount"]', minBid || '110')
    await page.click('button:has-text("Place Bid")')

    await expect(page.locator('text=Bid placed successfully')).toBeVisible()
    await expect(page.locator('[data-testid="current-price"]')).not.toHaveText(currentPrice || '')
  })

  test('bid below minimum shows error', async ({ page }) => {
    await page.goto('/auctions')
    const firstAuction = page.locator('[data-testid="auction-card"]').first()
    await firstAuction.click()

    const minBid = parseFloat((await page.locator('[data-testid="min-bid"]').textContent())?.replace(/[^0-9.]/g, '') || '110')

    await page.fill('input[name="amount"]', (minBid - 5).toString())
    await page.click('button:has-text("Place Bid")')

    await expect(page.locator('text=Bid must be at least')).toBeVisible()
  })

  test('user cannot bid on own auction', async ({ page }) => {
    // Login as seller
    await page.goto('/logout')
    await page.goto('/login')
    await page.fill('input[name="email"]', 'seller@example.com')
    await page.fill('input[name="password"]', 'password123')
    await page.click('button[type="submit"]')

    await page.goto('/seller/auctions')
    const firstAuction = page.locator('[data-testid="auction-card"]').first()
    await firstAuction.click()

    await page.fill('input[name="amount"]', '200')
    await page.click('button:has-text("Place Bid")')

    await expect(page.locator('text=Cannot bid on your own auction')).toBeVisible()
  })

  test('watch auction functionality', async ({ page }) => {
    await page.goto('/auctions')
    const firstAuction = page.locator('[data-testid="auction-card"]').first()
    await firstAuction.click()

    await page.click('button:has-text("Watch")')
    await expect(page.locator('text=Added to watchlist')).toBeVisible()

    await page.click('button:has-text("Unwatch")')
    await expect(page.locator('text=Removed from watchlist')).toBeVisible()
  })

  test('bid history displays correctly', async ({ page }) => {
    await page.goto('/auctions')
    const firstAuction = page.locator('[data-testid="auction-card"]').first()
    await firstAuction.click()

    await expect(page.locator('[data-testid="bid-history"]')).toBeVisible()
    await expect(page.locator('[data-testid="bid-history"] tbody tr')).toHaveCountGreaterThan(0)
  })
})

test.describe('Product Management (Seller)', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login')
    await page.fill('input[name="email"]', 'seller@example.com')
    await page.fill('input[name="password"]', 'password123')
    await page.click('button[type="submit"]')
  })

  test('seller can create a new product', async ({ page }) => {
    await page.goto('/seller/products/create')

    await page.fill('input[name="title"]', 'Test Product')
    await page.fill('textarea[name="description"]', 'Test description')
    await page.selectOption('select[name="category_id"]', 'electronics')
    await page.selectOption('select[name="condition"]', 'new')
    await page.fill('input[name="starting_price"]', '100')
    await page.fill('input[name="reserve_price"]', '150')

    // Upload image
    await page.setInputFiles('input[type="file"]', 'tests/fixtures/test-image.jpg')

    await page.click('button:has-text("Create Product")')

    await expect(page.locator('text=Product created successfully')).toBeVisible()
    await expect(page).toHaveURL(/.*seller\/products\/.*/)
  })

  test('seller can edit their product', async ({ page }) => {
    await page.goto('/seller/products')
    const firstProduct = page.locator('[data-testid="product-card"]').first()
    await firstProduct.locator('button:has-text("Edit")').click()

    await page.fill('input[name="title"]', 'Updated Product Title')
    await page.click('button:has-text("Save Changes")')

    await expect(page.locator('text=Product updated successfully')).toBeVisible()
  })

  test('seller can publish draft product', async ({ page }) => {
    await page.goto('/seller/products')
    const draftProduct = page.locator('[data-testid="product-card"][data-status="draft"]').first()

    if (await draftProduct.count() > 0) {
      await draftProduct.locator('button:has-text("Publish")').click()
      await expect(page.locator('text=Product published successfully')).toBeVisible()
    }
  })

  test('seller can create auction for published product', async ({ page }) => {
    await page.goto('/seller/products')
    const publishedProduct = page.locator('[data-testid="product-card"][data-status="published"]').first()

    if (await publishedProduct.count() > 0) {
      await publishedProduct.locator('button:has-text("Create Auction")').click()

      await page.fill('input[name="starting_price"]', '100')
      await page.fill('input[name="min_bid_increment"]', '10')
      await page.fill('input[name="reserve_price"]', '150')
      await page.fill('input[name="start_date"]', new Date(Date.now() + 300000).toISOString().slice(0, 16))
      await page.fill('input[name="end_date"]', new Date(Date.now() + 86400000).toISOString().slice(0, 16))

      await page.click('button:has-text("Create Auction")')

      await expect(page.locator('text=Auction created successfully')).toBeVisible()
    }
  })
})

test.describe('Notifications', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login')
    await page.fill('input[name="email"]', 'test@example.com')
    await page.fill('input[name="password"]', 'password123')
    await page.click('button[type="submit"]')
  })

  test('notification badge shows unread count', async ({ page }) => {
    await expect(page.locator('[data-testid="notification-badge"]')).toBeVisible()
  })

  test('clicking notification marks as read', async ({ page }) => {
    await page.click('[data-testid="notification-bell"]')
    await expect(page.locator('[data-testid="notification-dropdown"]')).toBeVisible()

    const firstNotification = page.locator('[data-testid="notification-item"]').first()
    if (await firstNotification.count() > 0) {
      await firstNotification.click()
      await expect(page.locator('[data-testid="notification-badge"]')).toHaveText('0')
    }
  })

  test('mark all as read works', async ({ page }) => {
    await page.click('[data-testid="notification-bell"]')
    await page.click('button:has-text("Mark all as read")')
    await expect(page.locator('[data-testid="notification-badge"]')).toHaveText('0')
  })
})

test.describe('Responsive Design', () => {
  test('mobile navigation works', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 })
    await page.goto('/')

    await expect(page.locator('[data-testid="mobile-menu-button"]')).toBeVisible()
    await page.click('[data-testid="mobile-menu-button"]')
    await expect(page.locator('[data-testid="mobile-menu"]')).toBeVisible()
  })

  test('auction cards stack on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 })
    await page.goto('/auctions')

    const cards = page.locator('[data-testid="auction-card"]')
    const firstCard = await cards.first().boundingBox()
    const secondCard = await cards.nth(1).boundingBox()

    if (firstCard && secondCard) {
      expect(firstCard.y).toBeLessThan(secondCard.y)
    }
  })
})