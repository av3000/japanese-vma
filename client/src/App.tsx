import { Provider } from 'react-redux';
import { BrowserRouter as Router } from 'react-router-dom';
import Footer from '@/components/features/Footer';
import Header from '@/components/features/Header';
import PageLoader from '@/components/features/PageLoader';
import { AuthProvider } from '@/contexts/AuthContext';
import ScrollToTop from '@/helpers/ScrollToTop';
import { useAuth } from '@/hooks/useAuth';
import AppRoutes from '@/routes/routes';
import { configureAppStore } from '@/store/store';

const store = configureAppStore();

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
	<Provider store={store}>
		<Router>
			<AuthProvider>
				<AppContent />
			</AuthProvider>
		</Router>
	</Provider>
);

export default App;
