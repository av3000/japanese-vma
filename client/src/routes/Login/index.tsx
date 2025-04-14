// @ts-nocheck
/* eslint-disable */
import React from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import LoginForm from '@/components/features/auth/Login';
import { authUser } from '@/store/slices/authSlice';
import { removeError } from '@/store/slices/errorsSlice';

const LoginPage: React.FC = () => {
	const dispatch = useDispatch();
	const errors = useSelector((state) => state.errors);

	const handleAuthUser = async (type, userData) => {
		return dispatch(authUser({ type, userData })).unwrap();
	};

	const handleRemoveError = () => {
		dispatch(removeError());
	};

	return (
		<LoginForm
			onAuth={handleAuthUser}
			removeError={handleRemoveError}
			errors={errors}
			buttonText="Sign in"
			heading="Welcome back."
		/>
	);
};

export default LoginPage;
