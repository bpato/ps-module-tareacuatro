{block name='tareacuatro'}
  {if isset($tareacuatro)}
  <style>
        #tareacuatro {
            text-align: center;
            position: relative;
            height: 300px;
        }

        #tareacuatro img {
            margin: 0 auto;
            z-index: 0;
        }

        #tareacuatro #message {
            position: absolute;
            top: 0;
            width: 100%;
            text-align: center;
            display: flex;
            flex-direction: column;
            height: 100%;
            justify-content: center;
        }
         #tareacuatro #message p {
             color: white;
             font-size: 1.5rem;
         }
      #tareacuatro .card{
         width: 300px;
         height: 60px;
         margin: 0 auto;
         position: relative;
         box-shadow: 1px 2px 6px rgba(0, 0, 0, 0.2);
        }    
        
      #tareacuatro .base, #scratch {
        cursor: default;
        height: 60px;
        width: 300px;
          position: absolute;
          top: 0;
          left: 0;
          cursor: grabbing;
      }
      #tareacuatro .base {
        line-height: 60px;
        text-align: center;
      }
      #tareacuatro #scratch {
        -webkit-tap-highlight-color: rgba(0, 0, 0, 0); 
        -webkit-touch-callout: none;
        -webkit-user-select: none;
      }
  </style>
    <div id="tareacuatro">
        <img src="/modules/tareacuatro/indice.png">
        <div id="message">
            <p>{$tareacuatro['voucher_amount']}</p>
            <div class="card">
                <div class="base">Coupon Code: {$tareacuatro['voucher_num']}</div>
                <canvas id="scratch" width="300" height="60"></canvas>
            </div>
        </div>
    </div>
  {/if}
{/block}
