defmodule PhoenixApiWeb.Router do
  use PhoenixApiWeb, :router

  pipeline :api do
    plug :accepts, ["json"]
    plug PhoenixApiWeb.Plugs.RateLimiter
  end

  scope "/api", PhoenixApiWeb do
    pipe_through :api

    get "/photos", PhotoController, :index
  end
end
