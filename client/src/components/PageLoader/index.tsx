import React from "react";
import "./PageLoader.css";
import { useSelector } from "react-redux";

const PageLoader: React.FC = () => {
  const { loading, loadingText, approximateLoad } = useSelector(
    (state: any) => state.application
  );

  if (!loading) return null;

  return (
    <div className="page-loader-container">
      <div className="page-loader">
        {loadingText && <h2>{loadingText}</h2>}
        {approximateLoad && <h5>{approximateLoad}</h5>}
      </div>
    </div>
  );
};

export default PageLoader;
