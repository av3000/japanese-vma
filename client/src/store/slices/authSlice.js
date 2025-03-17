import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import { apiCall, setTokenHeader } from "../../services/api";
import { HTTP_METHOD } from "../../shared/constants";
import { addError, removeError } from "./errorsSlice";

export const authUser = createAsyncThunk(
  "auth/authUser",
  async ({ type, userData }, { dispatch, rejectWithValue }) => {
    try {
      const { accessToken, ...response } = await apiCall(
        HTTP_METHOD.POST,
        `/api/${type}`,
        userData
      );

      localStorage.setItem("token", accessToken);
      setTokenHeader(accessToken);
      dispatch(removeError());
      return response.user;
    } catch (error) {
      if (error.email) {
        dispatch(addError(error.email));
      } else if (error.password) {
        dispatch(addError(error.password));
      } else if (error.name) {
        dispatch(addError(error.name));
      } else if (error.login) {
        dispatch(addError(error.login));
      } else {
        dispatch(addError(error));
      }
      return rejectWithValue(error);
    }
  }
);

const authSlice = createSlice({
  name: "auth",
  initialState: {
    isAuthenticated: false,
    user: {},
  },
  reducers: {
    setCurrentUser: (state, action) => {
      state.isAuthenticated = !!Object.keys(action.payload).length;
      state.user = action.payload;
    },
    logout: (state) => {
      localStorage.clear();
      setTokenHeader(false);
      state.isAuthenticated = false;
      state.user = {};
    },
  },
  extraReducers: (builder) => {
    builder.addCase(authUser.fulfilled, (state, action) => {
      state.isAuthenticated = true;
      state.user = action.payload;
    });
  },
});

// Export direct action creators
export const { setCurrentUser, logout } = authSlice.actions;

// Export utility function
export function setAuthorizationToken(token) {
  setTokenHeader(token);
}

// Export thunk for backward compatibility
export function authUserThunk(type, userData) {
  return (dispatch) => {
    return dispatch(authUser({ type, userData })).unwrap();
  };
}

export default authSlice.reducer;
