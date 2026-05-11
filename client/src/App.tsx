import { Provider as ReduxProvider } from 'react-redux';
import { BrowserRouter as Router } from 'react-router-dom';
import * as Sentry from '@sentry/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import Footer from '@/components/features/Footer';
import Header from '@/components/features/Header';
import SocketConnectionBanner from '@/components/features/SocketConnectionBanner';
import AppErrorFallback from '@/components/system/AppErrorFallback';
import ScrollToTop from '@/helpers/ScrollToTop';
import { AuthProvider } from '@/providers/contexts/auth-provider';
import { WebSocketProvider } from '@/providers/contexts/socket-provider';
import AppRoutes from '@/routes/routes';
import { configureAppStore } from '@/store/store';

const store = configureAppStore();

const queryClient = new QueryClient({
	defaultOptions: {
		queries: {
			staleTime: 1000 * 60 * 5, // Data is fresh for 5 minutes
			retry: 1,
		},
	},
});

const AppContent = () => {
	return (
		<div className="app-wrapper">
			<ScrollToTop />
			<Header />
			<SocketConnectionBanner />
			<main className="main-content">
				<AppRoutes />
			</main>
			<Footer />
		</div>
	);
};

const App = () => (
	<QueryClientProvider client={queryClient}>
		<ReduxProvider store={store}>
			<Router>
				<AuthProvider>
					<WebSocketProvider>
						<Sentry.ErrorBoundary fallback={AppErrorFallback}>
							<AppContent />
						</Sentry.ErrorBoundary>
					</WebSocketProvider>
				</AuthProvider>
			</Router>
		</ReduxProvider>
		<ReactQueryDevtools initialIsOpen={false} />
	</QueryClientProvider>
);

export default App;
