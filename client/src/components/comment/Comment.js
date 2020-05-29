import React from 'react';
import Moment from 'react-moment';
import { Link } from 'react-router-dom';
import DefaultAvatar from '../../assets/images/avatar-man.svg';

const Comment = ({ comment, articleId, editComment, deleteComment }) => (
    <div className="media">
        <img className="d-flex mr-3 rounder-circle" src={DefaultAvatar} alt="default-avatar"/>
        <div className="media-body">
            <div>
                <h5>@{comment.userName}</h5>
                <div className="btn-group">
                    <button onClick={editComment} className="btn btn-outline-primary btn-sm">Edit</button>
                    <button onClick={deleteComment} className="btn btn-outline-danger btn-sm">delete</button>
                </div>
            </div>
            <div>
                {comment.content}  
            </div>
            <br/>
            {/* edit btn */}
            {/* delete btn */}
            <div>
                {comment.likesTotal} likes <i className="fas fa-thumbs-up ml-1 mr-1 fa-lg"></i>     
                <p className="float-right">
                <Moment className="text-muted" format="Do MMM YYYY">
                    {comment.created_at}
                </Moment>
                </p>
            </div>
            <hr/>
        </div>
    </div>
);

export default Comment;


// <div className="card mb-4 shadow-sm">
//             <div className="card-body">
//                 <Link to={'/article/' + id} className="article-title-link">
//                     <h4 className="card-text article-title"> {title_jp}</h4> 
//                 </Link>
//                 <p className="text-muted">
//                     {/* <Moment className="text-muted" format="Do MMM YYYY">
//                         {created_at}
//                     </Moment> */}
//                     {jp_year} {jp_month} {jp_day} {jp_hour}
//                 </p>
//                 <p className="text-muted">
//                     {viewsTotal+30} views &nbsp; {commentsTotal} comments
//                     <img src={BookmarkImg} className="float-right" alt="bookmark"/>
//                 </p>
//                 <hr/>
//                 <div className="d-flex justify-content-between align-items-center text-muted">
//                         <span>
//                          {commentsTotal}&nbsp;N1 
//                         </span>
//                         <span>
//                          {viewsTotal}&nbsp;N2
//                         </span>
//                         <span>
//                          {likesTotal}&nbsp;N3
//                         </span>
//                         <span>
//                          {downloadsTotal}&nbsp;N4
//                         </span>
//                         <span>
//                          {downloadsTotal}&nbsp;N5
//                         </span>
//                 </div>
                
//             </div>
//         </div>