export const showLoader = (loadingText, approximateLoad) => dispatch => {
    dispatch({
        type: "SHOW_LOADER",
        loadingText,
        approximateLoad
    });
};

export const hideLoader = () => dispatch => {
    dispatch({
        type: "HIDE_LOADER"
    });
};