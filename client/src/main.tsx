// Put any other imports below so that CSS from your
// components takes precedence over default styles.
import { createRoot } from 'react-dom/client';
import { captureRenderError, initSentry } from '@/lib/monitoring/sentry';
import App from './App';
import './assets/font-awesome/css/fontawesome.min.css';
import './assets/font-awesome/css/regular.min.css';
import './assets/font-awesome/css/solid.min.css';
import './styles/App.scss';
import './styles/index.scss';
import './styles/tailwind.css';

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
