import React from 'react';
import Moment from 'react-moment';
import { Link } from 'react-router-dom';
import DefaultArticleImg from '../../assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import './ArticleItem.css';

const ArticleItem = ({ 
    created_at,
    title_jp, 
    commentsTotal, 
    viewsTotal, 
    likesTotal, 
    downloadsTotal,
    hashtags
}) => (
    <div className="col-md-4">
        <div className="card mb-4 shadow-sm">
            <Link to="/">
            <img src={DefaultArticleImg}
                alt="article-image"
                height="225" width="100%"
                className="timelines-image"     
            />
            </Link>
            <div className="card-body">
                <h4 className="card-text article-title">{title_jp}</h4> 
                <p> {hashtags.map(tag => <Link key={tag.id} className="tag-link" to="/">{tag.content} </Link>)} </p>
                <span className="text-muted">
                    <Moment className="text-muted" format="Do MMM YYYY">
                        {created_at}
                    </Moment>
                </span>
                <div className="d-flex justify-content-between align-items-center">
                    <div className="btn-group">
                        <span>
                        Comments: {commentsTotal}
                        </span>
                        <span>
                        Views: {viewsTotal}
                        </span>
                        <span>
                        Likes: {likesTotal}
                        </span>
                        <span>
                        Downloads: {downloadsTotal}
                        </span>
                    </div>
                </div>
                <div className="d-flex justify-content-between align-items-center">
                <div className="btn-group">
                        <span>
                        N1: {commentsTotal}
                        </span>
                        <span>
                        N2: {viewsTotal}
                        </span>
                        <span>
                        N3: {likesTotal}
                        </span>
                        <span>
                        N4: {downloadsTotal}
                        </span>
                        <span>
                        N5: {downloadsTotal}
                        </span>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
);

export default ArticleItem;