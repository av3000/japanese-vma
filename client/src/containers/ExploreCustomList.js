import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchLists } from '../store/actions/lists';
import ExploreListItem from '../components/list/ExploreListItem';
import Spinner from '../assets/images/spinner.gif';

class ExploreCustomList extends Component {
    componentDidMount() {
        this.props.fetchLists();
    }

    render() {
        const listTypes = [
            "knownradicals list",
            'knownkanjis list',
            'knownwords list',
            'knownsentences list',
            'Radicals List',
            'Kanjis List',
            'Words List',
            'Sentences List',
            'Articles List'
        ];

        let { lists } = this.props;

        let customLists = lists.data ? (lists.data.slice(0, 3).map(l => (
            <ExploreListItem
                key={l.id}
                id={l.id}
                listType={listTypes[l.type]}
                created_at={l.created_at}
                title={l.title}
                commentsTotal={l.commentsTotal}
                likesTotal={l.likesTotal}
                viewsTotal={l.viewsTotal}
                downloadsTotal={l.downloadsTotal}
                hashtags={l.hashtags.slice(0, 3)}
                itemsTotal={l.listItems.length}
                n1={l.n1}
                n2={l.n2}
                n3={l.n3}
                n4={l.n4}
                n5={l.n5}
            />
        )) ) : (
            <div className="container">
                <div className="row justify-content-center">
                    <img src={Spinner}/>
                </div>
            </div>
        )

        return customLists;
    }
}

function mapStateToProps(state) {
    return {
        lists: state.lists
    };
};

export default connect(mapStateToProps, { fetchLists })(ExploreCustomList);