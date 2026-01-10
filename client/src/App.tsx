import { Provider as ReduxProvider } from 'react-redux';
import { BrowserRouter as Router } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import Footer from '@/components/features/Footer';
import Header from '@/components/features/Header';
import PageLoader from '@/components/features/PageLoader';
import ScrollToTop from '@/helpers/ScrollToTop';
import { useAuth } from '@/hooks/useAuth';
import { AuthProvider } from '@/providers/contexts/auth-provider';
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
	const { isLoading } = useAuth();

	if (isLoading) {
		return <PageLoader />;
	}

	return (
		<div className="app-wrapper">
			<ScrollToTop />
			<Header />
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
					<AppContent />
				</AuthProvider>
			</Router>
		</ReduxProvider>
		<ReactQueryDevtools initialIsOpen={false} />
	</QueryClientProvider>
);

export default App;
