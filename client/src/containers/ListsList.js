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
        let { lists } = this.props;
        let customLists = lists.map(l => (
            <ListItem
                key={l.id}
                id={l.id}
                created_at={l.created_at}
                // jp_year={l.jp_year}
                // jp_month={l.jp_month}
                // jp_day={l.jp_day}
                // jp_hour={l.jp_hour}
                title={l.title}
                commentsTotal={l.commentsTotal}
                likesTotal={l.likesTotal}
                viewsTotal={l.viewsTotal}
                downloadsTotal={l.downloadsTotal}
                hashtags={l.hashtags}
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