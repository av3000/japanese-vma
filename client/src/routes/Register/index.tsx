// @ts-nocheck

import React from "react";
import { useDispatch, useSelector } from "react-redux";
import RegisterForm from "@/components/features/auth/Register";

import { authUser } from "@/store/slices/authSlice";
import { removeError } from "@/store/slices/errorsSlice";

const RegisterPage: React.FC = () => {
  const dispatch = useDispatch();
  const errors = useSelector((state) => state.errors);

  const handleAuthUser = async (type, userData) => {
    return dispatch(authUser({ type, userData })).unwrap();
  };

  const handleRemoveError = () => {
    dispatch(removeError());
  };

  return (
    <RegisterForm
      onAuth={handleAuthUser}
      removeError={handleRemoveError}
      errors={errors}
      buttonText="Sign up"
      heading="Join community today."
    />
  );
};

export default RegisterPage;
