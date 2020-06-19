import React from 'react';
import DashboardList from '../../containers/DashboardList';

const DashboardTimeline = ( {currentUser, props} ) => {
    return <DashboardList currentUser={currentUser} {...props} />
};

export default DashboardTimeline;