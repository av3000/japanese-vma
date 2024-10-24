FROM node:16.20.2-alpine3.18

WORKDIR /app

# caching package.json changes only
COPY package*.json ./

# cached modules
RUN npm install

COPY . .

EXPOSE 3000

CMD ["npm", "start"]
