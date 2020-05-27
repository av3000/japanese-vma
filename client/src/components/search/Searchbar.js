import React from 'react'

const Searchbar = () => {
    return (
        <div className="container">
            <div className="">
                <form className="form-inline d-flex md-form form-sm mt-2">
                <input className="form-control form-control-sm mr-3 w-25" type="text" placeholder="Search"
                    aria-label="Search"/>
                <i className="fas fa-search" aria-hidden="true"></i>
                </form>
            </div>
        </div>
    )
}

export default Searchbar;




