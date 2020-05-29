import React, { Component } from 'react'
import ListsList from '../../containers/ListsList';
import SearchBar from '../search/Searchbar';

const ListTimeline = props => {
        return (
            <div className="container">
            <div className="row mt-5">
                <SearchBar/>
                {/* by tag */}
                {/* by title keyword */}
                {/* by newest/popular */}
            </div>
            <div className="row mt-5">
                <ListsList/>
            {/* sidebar */}
            {/* ads bar */}
            {/* etc.... */}
            </div>
        </div>
        )
};

export default ListTimeline;