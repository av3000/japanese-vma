import React from 'react';
import { Provider } from 'react-redux';
import { configureStore } from '../../store';
import './App.css';
import { BrowserRouter as Router } from 'react-router-dom';
import { apiCall } from '../../services/api';
import { setAuthorizationToken, setCurrentUser } from '../../store/actions/auth';
import TopNavigationBar from '../navbar/TopNavigationBar';
import Footer from '../../components/footer/Footer';
import Main from '../Main';
import ScrollToTop from '../../components/util/scrolltotop/ScrollToTop';
import PageLoader from '../../components/PageLoader/PageLoader';

const store = configureStore()

if(localStorage.token) {
  // to save token after refresh
  setAuthorizationToken(localStorage.token);

  apiCall("get", `/api/user`)
    .then(res => { 
      store.dispatch(setCurrentUser( res ));
    })
    .catch(err => {
      console.log(err);
      store.dispatch(setCurrentUser({}));
    });
}

const App = () => (
  <Provider store={store}>
      <Router>
      <ScrollToTop/>
          <TopNavigationBar/>
          <Main/>
          <Footer/>
          <PageLoader />
      </Router>
  </Provider>
);

export default App;
