import React from 'react'

const Searchbar = () => {
    return (
            <div className="col-sm-12 col-md-8 col-lg-6">
                <form className="form-inline d-flex md-form form-sm mt-2">
                <input className="form-control form-control-sm mr-3 w-75" type="text" placeholder="Search"
                    aria-label="Search"/>
                <i className="fas fa-search" aria-hidden="true"></i>
                </form>
            </div>
    )
}

export default Searchbar;




