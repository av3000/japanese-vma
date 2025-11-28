import React from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import PageLoader from '@/components/features/PageLoader';
import { useAuth } from '@/hooks/useAuth';

const PrivateRoute: React.FC = () => {
	const location = useLocation();
	const { isAuthenticated, isLoading } = useAuth();

	if (isLoading) {
		return <PageLoader />;
	}

	return isAuthenticated ? <Outlet /> : <Navigate to="/login" state={{ from: location }} replace />;
};

export default PrivateRoute;
