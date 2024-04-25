# Use an official Node runtime as the base image
FROM node:16.20.2-alpine3.18

# Set the working directory in the container to /app
WORKDIR /app

# Copy package.json and package-lock.json from client directory to the working directory in the container to cache the dependencies
COPY ./client/package*.json ./

# Install the application dependencies
RUN npm install

# Copy the rest of the application from client directory to the working directory in the container
COPY ./client .

# Start the application
CMD ["npm", "start"]