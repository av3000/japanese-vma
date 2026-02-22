# TODO: migrate the node to v24 due to end of suppot for node 20
FROM node:20-alpine

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .

EXPOSE 3000
CMD ["npm", "run", "dev:docker"]