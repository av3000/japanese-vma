import React, { useEffect } from "react";
import { Provider, useDispatch, useSelector } from "react-redux";
import { BrowserRouter as Router, useNavigate } from "react-router-dom";
import { configureAppStore } from "@store/store";
import { apiCall, setTokenHeader } from "@services/api";
import { HTTP_METHOD } from "@shared/constants";
import TopNavigationBar from "../navbar/TopNavigationBar";
import Footer from "@components/footer/Footer";
import ScrollToTop from "@components/util/scrolltotop/ScrollToTop";
import PageLoader from "@components/PageLoader/PageLoader";
import Main from "../Main";
import "./App.css";

// Import actions from slices
import { setCurrentUser } from "@store/slices/authSlice";
import { showLoader, hideLoader } from "@store/slices/applicationSlice";

const store = configureAppStore();

const AppContent = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const isLoading = useSelector((state) => state.application.loading);

  useEffect(() => {
    if (localStorage.token) {
      dispatch(showLoader({ loadingText: "Loading your profile..." }));
      setTokenHeader(localStorage.token);
      apiCall(HTTP_METHOD.GET, `/api/user`)
        .then((res) => {
          dispatch(setCurrentUser(res));
        })
        .catch((err) => {
          console.error(err);
          dispatch(setCurrentUser({}));
          dispatch(hideLoader());
          navigate("/login");
        })
        .finally(() => dispatch(hideLoader()));
    } else {
      dispatch(hideLoader());
    }
  }, [dispatch, navigate]);

  if (isLoading) {
    return <PageLoader />;
  }

  return (
    <div className="app-wrapper">
      <ScrollToTop />
      <TopNavigationBar />
      <Main />
      <Footer />
    </div>
  );
};

const App = () => (
  <Provider store={store}>
    <Router>
      <AppContent />
    </Router>
  </Provider>
);

export default App;