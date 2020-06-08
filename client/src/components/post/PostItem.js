import React from 'react';
// import Moment from 'react-moment';
import { Link } from 'react-router-dom';
import Moment from 'react-moment';


const PostItem = ({ 
    id,
    title,
    content,
    type,
    locked,
    userId,
    commentsTotal,
    likesTotal,
    viewsTotal,
    downloadsTotal,
    hashtags,
    user,
    postType,
    date
}) => (
    <div className="row border-bottom border-gray">
        <div className="col-lg-10 col-md-12 col-12-sm pb-3 pt-3">
            <p>
                <strong className="d-block text-gray-dark">{user.name}</strong>
            </p>
            <h5>
                <Link to={`/community/${id}`}>
                    {title}
                </Link>
            </h5>
            Date: <Moment className="text-muted" format="Do MMM YYYY"> 
                {date} 
            </Moment>
            <br/>
            Tags: {hashtags.map(tag => <span key={tag.id} className="tag-link">{tag.content} </span>)}
        </div>
        <div className="col-lg-2 col-12-sm pt-3">
            <small>
            <span>
                    <strong className="d-block text-gray-dark">{postType}</strong>
            </span>
            <span>
                {commentsTotal}&nbsp;Comments 
            </span> <br/>
            <span>
                {viewsTotal}&nbsp;Views
            </span> <br/>
            <span>
                {likesTotal}&nbsp;Likes &nbsp;
            </span> <br/>
            </small>
        </div>
    </div>
);

export default PostItem;