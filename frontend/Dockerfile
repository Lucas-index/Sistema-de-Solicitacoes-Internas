FROM node:20-alpine AS build

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .

ARG VITE_API_URL
ARG VITE_STORAGE_URL
ARG VITE_PYTHON_API_URL
ARG VITE_PYTHON_API_KEY

ENV VITE_API_URL=$VITE_API_URL
ENV VITE_STORAGE_URL=$VITE_STORAGE_URL
ENV VITE_PYTHON_API_URL=$VITE_PYTHON_API_URL
ENV VITE_PYTHON_API_KEY=$VITE_PYTHON_API_KEY

RUN npm run build

FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
