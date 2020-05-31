import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchLists } from '../store/actions/lists';
import ListItem from '../components/list/ListItem';

class ListsList extends Component {
    componentDidMount() {
        this.props.fetchLists();
    console.log("fetchLists compounent did mount")
    console.log(this.props.lists);

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
        let customLists = lists.map(l => (
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
        ));

        return customLists;
    }
}

function mapStateToProps(state) {
    return {
        lists: state.lists
    };
};

export default connect(mapStateToProps, { fetchLists })(ListsList);