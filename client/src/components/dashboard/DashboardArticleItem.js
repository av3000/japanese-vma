import React from 'react';
import Moment from 'react-moment';
import { Link } from 'react-router-dom';
import DefaultArticleImg from '../../assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';

const DashboardArticleItem = ({ 
    
    id,
    created_at,
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
            <div className="col-md-8 pb-3 mb-0 border-bottom border-gray">
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
                         {commentsTotal}&nbsp;Comments&nbsp;
                        </span>
                        <span>
                         {viewsTotal} &nbsp;Views&nbsp;
                        </span>
                        <span>
                         {likesTotal} &nbsp;Likes &nbsp;
                        </span>
                </small>
                <small className="d-block text-muted">
                    <span>
                    <Moment className="text-muted" format="Do MMM YYYY">
                      {created_at}
                    </Moment>
                    </span>
                </small>
            </div>
    </div>
);

export default DashboardArticleItem;