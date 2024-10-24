import axios from "axios";

export function setTokenHeader(token) {
  if (token) {
    axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
  } else {
    delete axios.defaults.headers.common["Authorization"];
  }
}

/**
 *
 * @param {string} method the HTTP verb you want to use
 * @param {string} path the route path / endpoint
 * @param {object} data (optional) data in JSON form for POST requests
 */

export function apiCall(method, path, data) {
  const apiUrl = !path.includes("localhost:8080")
    ? process.env.REACT_APP_API_URL + path
    : path;

  return new Promise((resolve, reject) => {
    return axios[method](apiUrl, data)
      .then((res) => {
        return resolve(res.data);
      })
      .catch((err) => {
        console.log(err);
        if (err.response) {
          // The request was made and the server responded with a status code
          // that falls out of the range of 2xx
          return reject(err.response.data.error);
        } else if (err.request) {
          // The request was made but no response was received
          return reject("No response was received");
        } else {
          // Something happened in setting up the request that triggered an Error
          return reject(err.message);
        }
      });
  });
}
