import axios from "axios";

export function setTokenHeader(token) {
  if (token) {
    axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
  } else {
    delete axios.defaults.headers.common["Authorization"];
  }
}

/**
 * Make an API call
 * @param {string} method the HTTP verb you want to use
 * @param {string} path the route path / endpoint
 * @param {object} data (optional) data in JSON form for POST requests
 */
export function apiCall(method, path, data) {
  // With Vite proxy, we can just use the path directly
  // The proxy will forward all /api requests to your backend
  const axiosMethod = method.toLowerCase();
  
  console.log(`Making ${axiosMethod} request to ${path}`, data);

  // For GET requests, pass data as params
  const config = axiosMethod === 'get' && data ? { params: data } : {};
  
  return new Promise((resolve, reject) => {
    // For GET and DELETE requests
    if (axiosMethod === 'get' || axiosMethod === 'delete') {
      return axios[axiosMethod](path, config)
        .then(res => {
          console.log(`Response from ${path}:`, res.data);
          resolve(res.data);
        })
        .catch(err => handleError(err, reject));
    } 
    // For POST, PUT, PATCH requests
    else {
      return axios[axiosMethod](path, data, config)
        .then(res => {
          console.log(`Response from ${path}:`, res.data);
          resolve(res.data);
        })
        .catch(err => handleError(err, reject));
    }
  });
}

function handleError(err, reject) {
  console.error("API Call Error:", err);
  
  if (err.response) {
    const errorMsg = err.response.data.error || 
                    (typeof err.response.data === 'string' ? err.response.data : 
                    JSON.stringify(err.response.data));
    return reject(errorMsg);
  } else if (err.request) {
    return reject("No response received from server");
  } else {
    return reject(err.message || "Error making request");
  }
}