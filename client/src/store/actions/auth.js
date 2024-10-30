import { apiCall, setTokenHeader } from "../../services/api";
import { HTTP_METHOD } from "../../shared/constants";
import { SET_CURRENT_USER } from "../actionTypes";
import { addError, removeError } from "./errors";

export function setCurrentUser(user) {
  return {
    type: SET_CURRENT_USER,
    user,
  };
}

export function setAuthorizationToken(token) {
  setTokenHeader(token);
}

export function logout() {
  return (dispatch) => {
    localStorage.clear();
    setAuthorizationToken(false);
    dispatch(setCurrentUser({}));
  };
}

export function authUser(type, userData) {
  return (dispatch) => {
    return new Promise((resolve, reject) => {
      return apiCall(HTTP_METHOD.POST, `/api/${type}`, userData)
        .then(({ accessToken, ...user }) => {
          localStorage.setItem("token", accessToken);
          setAuthorizationToken(accessToken);
          dispatch(setCurrentUser(user.user));
          dispatch(removeError);
          resolve();
        })
        .catch((error) => {
          if (error.email) {
            dispatch(addError(error.email));
          } else if (error.password) {
            dispatch(addError(error.password));
          } else if (error.name) {
            dispatch(addError(error.name));
          } else if (error.login) {
            dispatch(addError(error.login));
          }
          reject();
        });
    });
  };
}
