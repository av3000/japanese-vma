const initialState = {
  loading: false,
  loadingText: "default text",
  approximateLoad: "",
};

export default (state = initialState, action) => {
  switch (action.type) {
    case "SHOW_LOADER":
      return {
        ...state,
        loading: true,
        loadingText: action.loadingText ?? "JPLearning is Loading...",
        approximateLoad: action.approximateLoad,
      };

    case "HIDE_LOADER":
      return { ...state, loading: false };

    default:
      return state;
  }
};
