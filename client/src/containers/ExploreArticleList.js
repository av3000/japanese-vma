import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchArticles } from '../store/actions/articles';
import ExploreArticleItem from '../components/article/ExploreArticleItem';

class ExploreArticleList extends Component {
    componentDidMount() {
        this.props.fetchArticles();
    }

    render() {
        let { articles } = this.props;
        articles = articles.slice(0,3);
        let articleList = articles.map(a => (
            <ExploreArticleItem
                key={a.id}
                id={a.id}
                created_at={a.created_at}
                jp_year={a.jp_year}
                jp_month={a.jp_month}
                jp_day={a.jp_day}
                jp_hour={a.jp_hour}
                title_jp={a.title_jp}
                commentsTotal={a.commentsTotal}
                likesTotal={a.likesTotal}
                viewsTotal={a.viewsTotal}
                downloadsTotal={a.downloadsTotal}
                hashtags={a.hashtags}
            />
        ));

        return articleList;
    }
}

function mapStateToProps(state) {
    return {
        articles: state.articles
    };
};

export default connect(mapStateToProps, { fetchArticles })(ExploreArticleList);