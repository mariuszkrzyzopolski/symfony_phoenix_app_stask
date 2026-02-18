defmodule PhoenixApiWeb.Plugs.RateLimiter do
  import Plug.Conn
  use Plug.Builder
  
  def init(opts), do: opts
  
  def call(conn, _opts) do
    client_ip = get_client_ip(conn)
    
    case check_rate_limits(client_ip) do
      :ok -> 
        conn
      {:error, :rate_limited, limit} ->
        conn
        |> put_resp_content_type("application/json")
        |> send_resp(429, Jason.encode!(%{
          error: "Rate limit exceeded",
          limit: limit,
          retry_after: get_retry_after(limit)
        }))
        |> halt()
    end
  end
  
  defp check_rate_limits(client_ip) do
    case Hammer.check_rate("ip:#{client_ip}:short", 600_000, 5) do
      {:allow, _count} -> 
        case Hammer.check_rate("ip:#{client_ip}:hour", 3_600_000, 1000) do
          {:allow, _count} -> :ok
          {:deny, _limit} -> {:error, :rate_limited, "1000 requests per hour"}
        end
      {:deny, _limit} -> {:error, :rate_limited, "5 requests per 10 minutes"}
    end
  end
  
  defp get_client_ip(conn) do
    case get_req_header(conn, "x-forwarded-for") do
      [ip | _] -> ip
      [] -> to_string(:inet.ntoa(conn.remote_ip))
    end
  end
  
  defp get_retry_after("5 requests per 10 minutes"), do: "600"
  defp get_retry_after("1000 requests per hour"), do: "3600"
  defp get_retry_after(_), do: "60"
end