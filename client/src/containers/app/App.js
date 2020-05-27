import React from 'react';
import { Provider } from 'react-redux';
import { configureStore } from '../../store';
import './App.css';
import { BrowserRouter as Router } from 'react-router-dom';
import Navbar from '../navbar/Navbar';
import Footer from '../../components/footer/Footer';
import Main from '../Main';
import { apiCall } from '../../services/api';
import ScrollToTop from '../../components/util/scrolltotop/ScrollToTop';
import SearchBar from '../../components/search/Searchbar';
import { setAuthorizationToken, setCurrentUser } from '../../store/actions/auth';

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
        <div className="onboarding">
          <Navbar/>
          <SearchBar/>
          <Main/>
          <Footer/>
        </div>
      </Router>
  </Provider>
);

export default App;
