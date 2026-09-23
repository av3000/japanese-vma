// Put any other imports below so that CSS from your
// components takes precedence over default styles.
import { createRoot } from 'react-dom/client';
import '@fontsource/ibm-plex-mono/400.css';
import '@fontsource/ibm-plex-mono/500.css';
import '@fontsource/ibm-plex-mono/600.css';
import '@fontsource/ibm-plex-mono/700.css';
import '@fontsource/ibm-plex-sans/400.css';
import '@fontsource/ibm-plex-sans/500.css';
import '@fontsource/ibm-plex-sans/600.css';
import '@fontsource/ibm-plex-sans/700.css';
import { captureRenderError, initSentry } from '@/lib/monitoring/sentry';
import App from './App';
import './assets/font-awesome/css/fontawesome.min.css';
import './assets/font-awesome/css/regular.min.css';
import './assets/font-awesome/css/solid.min.css';
import './styles/App.css';
import './styles/index.css';

const rootElement = document.getElementById('root');

// Verify the element exists before creating the root
if (!rootElement) {
	throw new Error("Failed to find the root element with id 'root'");
}

initSentry();

// Create the root with the non-null element
const root = createRoot(rootElement, {
	onUncaughtError: (error, errorInfo) => {
		void captureRenderError(error, { componentStack: errorInfo.componentStack });
	},
	onRecoverableError: (error, errorInfo) => {
		void captureRenderError(error, { componentStack: errorInfo.componentStack });
	},
});

root.render(<App />);
