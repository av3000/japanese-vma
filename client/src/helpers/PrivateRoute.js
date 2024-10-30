import React from "react";
import { Redirect, Route } from "react-router-dom";
import { useSelector } from "react-redux";

const PrivateRoute = ({ component: Component, componentProps, ...rest }) => {
  const isAuthenticated = useSelector(
    (state) => state.currentUser.isAuthenticated
  );
  const loading = useSelector((state) => state.application.loading);

  if (loading) return null;

  return (
    <Route
      {...rest}
      render={(props) =>
        isAuthenticated ? (
          <Component {...props} {...componentProps} />
        ) : (
          <Redirect
            to={{
              pathname: "/login",
              state: { from: props.location },
            }}
          />
        )
      }
    />
  );
};

export default PrivateRoute;
