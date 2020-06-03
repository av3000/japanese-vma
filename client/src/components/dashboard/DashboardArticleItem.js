import React from 'react';
// import Moment from 'react-moment';
import { Link } from 'react-router-dom';
import DefaultArticleImg from '../../assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';

const DashboardArticleItem = ({ 
    
    id,
    title,
    content,
    publicity,
    commentsTotal,
    likesTotal,
    viewsTotal,
    downloadsTotal,
    hashtags,
    user
}) => (
    <div className="row">
            <div className="col-md-8 pb-3 mb-0 small  border-bottom border-gray">
                <p>
                    {title}
                </p>
                tags: {hashtags.map(tag => <span key={tag.id} className="tag-link">{tag.content} </span>)}
            </div>
            <div className="col-md-4">
                <Link to={`/article/${id}`}>
                    <strong className="d-block text-gray-dark float-right">
                         <i className="fas fa-external-link-alt"></i>
                    </strong>
                </Link>
                <small className="d-flex justify-content-between align-items-center text-muted">
                        <span>
                         {commentsTotal}&nbsp;Comments 
                        </span>
                        <span>
                         {viewsTotal}&nbsp;Views
                        </span>
                        <span>
                         {likesTotal}&nbsp;Likes &nbsp;
                        </span>
                </small>
            </div>
    </div>
);

export default DashboardArticleItem;