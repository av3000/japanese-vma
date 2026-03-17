// Put any other imports below so that CSS from your
// components takes precedence over default styles.
import { createRoot } from 'react-dom/client';

import App from './App';
import { initSentry, sentryReactErrorHandler } from '@/lib/monitoring/sentry';
import './assets/font-awesome/css/all.min.css';
import './styles/tailwind.css';
import './styles/App.scss';
import './styles/index.scss';

const rootElement = document.getElementById('root');

// Verify the element exists before creating the root
if (!rootElement) {
  throw new Error("Failed to find the root element with id 'root'");
}

initSentry();

// Create the root with the non-null element
const root = createRoot(rootElement, {
  onUncaughtError: sentryReactErrorHandler(),
  onRecoverableError: sentryReactErrorHandler(),
});

root.render(<App />);
