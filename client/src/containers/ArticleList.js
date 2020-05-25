import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchArticles } from '../store/actions/articles';
import ArticleItem from '../components/article/ArticleItem';

class ArticleList extends Component {
    componentDidMount() {
        this.props.fetchArticles();
    }

    render() {
        const { articles } = this.props;
        console.log("props are ARTICLES: ")
        console.log(articles);
        let articleList = articles.map(a => (
            <ArticleItem
                key={a.id}
                created_at={a.created_at}
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

export default connect(mapStateToProps, { fetchArticles })(ArticleList);