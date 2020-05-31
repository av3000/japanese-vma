import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchArticles, removeArticle } from '../store/actions/articles';
import ArticleItem from '../components/article/ArticleItem';


class ArticleList extends Component {
    componentDidMount() {
        this.props.fetchArticles();
    }

    render() {
        const { articles, currentUser } = this.props;
        let articleList = articles.map(a => (
                <ArticleItem
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
                    hashtags={a.hashtags.slice(0, 3)}
                    // bookmarkArticle={bookmarkArticle.bind(this, a.id)}
                    // removeArticle={removeArticle.bind(this, a.id)}
                    // isCorrectUser={currentUser === a.user_id}
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