import React, { Component } from 'react';
import ReactDOM from 'react-dom';
// import App component
import App from './components/App'

// change the getElementId from example to app 
// render App component instead of Example
if (document.getElementById('root')) {
    ReactDOM.render(<App />, document.getElementById('root'));
}