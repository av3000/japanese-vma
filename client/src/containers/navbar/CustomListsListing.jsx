import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchLists } from '../store/actions/lists';
import CustomListItem from '../components/list/CustomListItem';

class CustomListsListing extends Component {
    componentDidMount() {
        this.props.fetchLists();
    }

    render() {
        let { lists } = this.props;
        lists = lists.slice(0,3);
        let customLists = lists.map(l => (
            <CustomListItem
                key={l.id}
                id={l.id}
                created_at={l.created_at}
                jp_year={l.jp_year}
                jp_month={l.jp_month}
                jp_day={l.jp_day}
                jp_hour={l.jp_hour}
                title_jp={l.title_jp}
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

export default connect(mapStateToProps, { fetchLists })(CustomListsListing);