<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-secondary-50 dark:from-secondary-900 dark:via-secondary-950 dark:to-secondary-900">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-20">
        <div class="max-w-2xl w-full text-center">
            <svg class="mx-auto h-24 w-24 text-primary-600 dark:text-primary-400 mb-8" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" fill="none"/>
            </svg>
            
            <h1 class="text-5xl font-bold text-secondary-900 dark:text-white mb-4">Auction Marketplace</h1>
            <p class="text-xl text-secondary-600 dark:text-secondary-400 mb-12 max-w-xl mx-auto">
                A distributed, production-ready online auction platform built with Laravel, Vue.js, MongoDB, and Redis.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <a href="/auctions" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-4 rounded-xl font-medium transition-colors shadow-lg">
                    Browse Auctions
                </a>
                <a href="/register" class="bg-white dark:bg-secondary-800 hover:bg-secondary-50 dark:hover:bg-secondary-700 text-primary-600 dark:text-primary-400 px-6 py-4 rounded-xl font-medium transition-colors border border-primary-200 dark:border-primary-800 shadow-lg">
                    Start Selling
                </a>
                <a href="/login" class="border-2 border-primary-600 text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 px-6 py-4 rounded-xl font-medium transition-colors">
                    Sign In
                </a>
            </div>

            <div class="border-t border-secondary-200 dark:border-secondary-800 pt-8">
                <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-4">API Endpoints</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                    <div class="bg-white/50 dark:bg-secondary-800/50 p-4 rounded-lg">
                        <code class="text-sm text-primary-600 dark:text-primary-400">GET /api/v1/auctions</code>
                        <p class="text-sm text-secondary-600 dark:text-secondary-400 mt-1">List all auctions</p>
                    </div>
                    <div class="bg-white/50 dark:bg-secondary-800/50 p-4 rounded-lg">
                        <code class="text-sm text-primary-600 dark:text-primary-400">POST /api/v1/auth/login</code>
                        <p class="text-sm text-secondary-600 dark:text-secondary-400 mt-1">Authenticate user</p>
                    </div>
                    <div class="bg-white/50 dark:bg-secondary-800/50 p-4 rounded-lg">
                        <code class="text-sm text-primary-600 dark:text-primary-400">POST /api/v1/auctions/{id}/bids</code>
                        <p class="text-sm text-secondary-600 dark:text-secondary-400 mt-1">Place a bid</p>
                    </div>
                    <div class="bg-white/50 dark:bg-secondary-800/50 p-4 rounded-lg">
                        <code class="text-sm text-primary-600 dark:text-primary-400">GET /api/v1/dashboard/overview</code>
                        <p class="text-sm text-secondary-600 dark:text-secondary-400 mt-1">User dashboard</p>
                    </div>
                </div>
            </div>

            <div class="mt-12">
                <a href="/docs/v1" class="inline-flex items-center gap-2 text-primary-600 dark:text-primary-400 hover:underline font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    View API Documentation (Swagger)
                </a>
            </div>
        </div>
    </div>
</body>
</html>