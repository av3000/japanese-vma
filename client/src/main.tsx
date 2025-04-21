// Put any other imports below so that CSS from your
// components takes precedence over default styles.
import { createRoot } from 'react-dom/client';

import App from './App';
import './assets/font-awesome/css/all.min.css';
import './styles/App.scss';
import './styles/index.scss';

// Get the root element
const rootElement = document.getElementById('root');

// Verify the element exists before creating the root
if (!rootElement) {
  throw new Error("Failed to find the root element with id 'root'");
}

// Create the root with the non-null element
const root = createRoot(rootElement);

// Render the app
root.render(<App />);
