import { createSlice } from "@reduxjs/toolkit";

const errorsSlice = createSlice({
  name: "errors",
  initialState: { message: null },
  reducers: {
    addError: (state, action) => {
      state.message = action.payload;
    },
    removeError: (state) => {
      state.message = null;
    },
  },
});

export const { addError, removeError } = errorsSlice.actions;
export default errorsSlice.reducer;
