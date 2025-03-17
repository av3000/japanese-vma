import React from "react";
import { Navigate, Outlet, useLocation } from "react-router-dom";
import { useSelector } from "react-redux";

const ProtectedRoute = () => {
  const location = useLocation();
  const isAuthenticated = useSelector(
    (state) => state.currentUser.isAuthenticated
  );
  const loading = useSelector((state) => state.application.loading);

  if (loading) return null;
  
  return isAuthenticated ? (
    <Outlet />
  ) : (
    <Navigate to="/login" state={{ from: location }} replace />
  );
};

export default ProtectedRoute;