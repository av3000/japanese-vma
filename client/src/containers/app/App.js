import React from 'react';
import { Provider } from 'react-redux';
import { configureStore } from '../../store';
import './App.css';
import { BrowserRouter as Router } from 'react-router-dom';
import Navbar from '../navbar/Navbar';
import Footer from '../../components/footer/Footer';
import Main from '../Main';
import { setAuthorizationToken, setCurrentUser } from '../../store/actions/auth';

const store = configureStore()

if(localStorage.token) {
  // to save token after refresh
  setAuthorizationToken(localStorage.token);

  // TODO: find the passport way of jwt_decode.
  // *securing from token temptation. 
  // try {
  //   store.dispatch(setCurrentUser( jwt_decode(localStorage.token) ));
  // } catch(e) {
  //   store.dispatch(setCurrentUser({}));
  // }
  //
  store.dispatch(setCurrentUser(localStorage.token));

}

const App = () => (
  <Provider store={store}>
      <Router>
        <div className="onboarding">
          <Navbar/>
          <Main/>
          <Footer/>
        </div>
      </Router>
  </Provider>
);

export default App;
