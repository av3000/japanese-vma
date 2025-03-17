import { createSlice } from "@reduxjs/toolkit";

const applicationSlice = createSlice({
  name: "application",
  initialState: {
    loading: false,
    loadingText: "default text",
    approximateLoad: "",
  },
  reducers: {
    showLoader: (state, action) => {
      state.loading = true;
      state.loadingText =
        action.payload?.loadingText ?? "JPLearning is Loading...";
      state.approximateLoad = action.payload?.approximateLoad || "";
    },
    hideLoader: (state) => {
      state.loading = false;
    },
  },
});

// Action creators
export const { showLoader, hideLoader } = applicationSlice.actions;

// Thunk action creators (for backward compatibility)
export const showLoaderThunk = (loadingText, approximateLoad) => (dispatch) => {
  dispatch(showLoader({ loadingText, approximateLoad }));
};

export default applicationSlice.reducer;
