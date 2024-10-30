import React, { useEffect } from "react";
import { Provider, useDispatch, useSelector } from "react-redux";
import { BrowserRouter as Router, useHistory } from "react-router-dom";

import { configureStore } from "../../store";
import {
  setAuthorizationToken,
  setCurrentUser,
} from "../../store/actions/auth";
import { showLoader, hideLoader } from "../../store/actions/application";
import { apiCall } from "../../services/api";
import { HTTP_METHOD } from "../../shared/constants";
import TopNavigationBar from "../navbar/TopNavigationBar";
import Footer from "../../components/footer/Footer";
import ScrollToTop from "../../components/util/scrolltotop/ScrollToTop";
import PageLoader from "../../components/PageLoader/PageLoader";
import Main from "../Main";

const store = configureStore();

const AppContent = () => {
  const dispatch = useDispatch();
  const history = useHistory();
  const isLoading = useSelector((state) => state.application.loading);

  useEffect(() => {
    if (localStorage.token) {
      dispatch(showLoader());
      setAuthorizationToken(localStorage.token);

      apiCall(HTTP_METHOD.GET, `/api/user`)
        .then((res) => {
          dispatch(setCurrentUser(res));
        })
        .catch((err) => {
          console.error(err);
          dispatch(setCurrentUser({}));
          dispatch(hideLoader());
          history.push("/login");
        })
        .finally(() => dispatch(hideLoader()));
    } else {
      dispatch(hideLoader());
    }
  }, [dispatch, history]);

  if (isLoading) {
    return <PageLoader />;
  }

  return (
    <>
      <ScrollToTop />
      <TopNavigationBar />
      <Main />
      <Footer />
    </>
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
