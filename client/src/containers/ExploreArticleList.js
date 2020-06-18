import React, { Component } from 'react';
// import { connect } from 'react-redux';
// import { fetchArticles } from '../store/actions/articles';
import { apiCall } from '../services/api';
import ExploreArticleItem from '../components/article/ExploreArticleItem';
import Spinner from '../assets/images/spinner.gif';

class ExploreArticleList extends Component {
    _isMounted = false;
    constructor(props) {
        super(props);
        this.state = {
            articles: [],
        }
    }

    componentDidMount() {
        this._isMounted = true;
        this.fetchArticles();
    }

    componentWillUnmount() {
        this._isMounted = false;
      }

    fetchArticles(){
        return apiCall("get", "/api/articles")
            .then(res => {
                if (this._isMounted) {
                    let newState = Object.assign({}, this.state);
                    newState.articles = [...newState.articles, ...res.articles.data];
                    this.setState( newState );
                }
            })
            .catch(err => {
                console.log(err);
            })
    };

    render() {
        let articleList = this.state.articles ? ( this.state.articles.map(a => (
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
                    hashtags={a.hashtags.slice(0, 3)}
                    n1={a.n1}
                    n2={a.n2}
                    n3={a.n3}
                    n4={a.n4}
                    n5={a.n5}
                    uncommon={a.uncommon}
                />
        )) ) : (
        <div className="container">
            <div className="row justify-content-center">
                <img src={Spinner}/>
            </div>
        </div>
        );

        return articleList;
    }
}

// function mapStateToProps(state) {
//     return {
//         articles: state.articles
//     };
// };

// export default connect(mapStateToProps, { fetchArticles })(ExploreArticleList);
export default ExploreArticleList;