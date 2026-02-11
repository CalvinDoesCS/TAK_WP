# Frontend Dockerfile: build React/Vite app and serve static assets (including compiled WASM)

FROM node:18-alpine AS build
WORKDIR /app

# copy package files and install
COPY package.json package-lock.json ./
RUN npm ci --silent

# copy source and build
COPY . .
# Ensure that compiled WASM files are placed into `public/wasm` or `dist/wasm` during your build
RUN npm run build

# production image
FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html

# if you compile WASM to a different folder, copy it into nginx html directory
# COPY --from=build /app/public/wasm /usr/share/nginx/html/wasm

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
