# syntax=docker/dockerfile:1.7

# Build stage - Node 23 (latest)
FROM node:23-alpine AS builder

WORKDIR /app

# Install build dependencies
RUN apk add --no-cache python3 make g++

COPY package*.json ./

# Use npm install (works without package-lock.json)
RUN --mount=type=cache,target=/root/.npm,id=npm-cache \
    npm install --prefer-offline --no-audit --no-fund

COPY . .

# Fix bin links and build
RUN npm rebuild && npm run build:prod

# Production stage - nginx 1.31 mainline
FROM nginx:1.31-alpine AS production

# Copy built assets
COPY --from=builder /app/dist /usr/share/nginx/html

# Copy nginx config
COPY nginx.conf /etc/nginx/nginx.conf

# Fix permissions for nginx to run as non-root
RUN mkdir -p /var/cache/nginx /var/log/nginx /run/nginx \
    && chown -R nginx:nginx /var/cache/nginx /var/log/nginx /run/nginx /usr/share/nginx/html

USER nginx

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
    CMD wget -q --spider http://127.0.0.1/health || exit 1

CMD ["nginx", "-g", "daemon off;"]