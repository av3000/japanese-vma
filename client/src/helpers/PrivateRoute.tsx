import React from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { Container } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import styles from './PrivateRoute.module.css';

const PrivateRoute: React.FC = () => {
	const location = useLocation();
	const { isAuthenticated, isLoading } = useAuth();

	if (isLoading) {
		return (
			<Container className={styles.page} role="status" aria-label="Checking session">
				<span className={styles.placeholder} />
			</Container>
		);
	}

	return isAuthenticated ? <Outlet /> : <Navigate to="/login" state={{ from: location }} replace />;
};

export default PrivateRoute;
