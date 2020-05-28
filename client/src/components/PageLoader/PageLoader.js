import React, { Component } from 'react'
import './PageLoader.css';
import { connect } from 'react-redux';

export class PageLoader extends Component {
    state = { }

    render() {

        const { loading, loadingText, approximateLoad } = this.props;

        if( !loading ) return null;

        else {
            return (
                <div className="">
                    <div className="loader-container">
                        <div className="loader">
                            <h2>{loadingText}</h2>
                            <h5>{approximateLoad}</h5>
                        </div>
                    </div>
                </div>
            )
        }
    };
};

const mapStateToProps = state => ({ 
    loading: state.application.loading,
    loadingText: state.application.loadingText,
    approximateLoad: state.application.approximateLoad
});

export default connect(mapStateToProps)(PageLoader);
