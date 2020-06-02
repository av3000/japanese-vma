import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchLists } from '../store/actions/lists';
import ListItem from '../components/list/ListItem';
import Spinner from '../assets/images/spinner.gif';

class ListsList extends Component {
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
        let customLists = lists.data ? (lists.data.map(l => (
            <ListItem
                key={l.id}
                id={l.id}
                listType={listTypes[l.type]}
                created_at={l.created_at}
                title={l.title}
                commentsTotal={l.commentsTotal}
                itemsTotal={l.listItems.length}
                likesTotal={l.likesTotal}
                viewsTotal={l.viewsTotal}
                downloadsTotal={l.downloadsTotal}
                hashtags={l.hashtags.slice(0, 3)}
                listItems={l.listItems}
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

export default connect(mapStateToProps, { fetchLists })(ListsList);