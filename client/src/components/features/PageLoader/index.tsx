import React from 'react';
import { useSelector } from 'react-redux';

import styles from './PageLoader.module.scss';

interface ApplicationState {
  loading: boolean;
  loadingText?: string;
  approximateLoad?: string;
}

interface RootState {
  application: ApplicationState;
}

const PageLoader: React.FC = () => {
  const { loading, loadingText, approximateLoad } = useSelector(
    (state: RootState) => state.application,
  );

  if (!loading) return null;

  return (
    <div className={styles.pageLoaderContainer}>
      <div className={styles.pageLoader}>
        {loadingText && <h2>{loadingText}</h2>}
        {approximateLoad && <h5>{approximateLoad}</h5>}
      </div>
    </div>
  );
};

export default PageLoader;
